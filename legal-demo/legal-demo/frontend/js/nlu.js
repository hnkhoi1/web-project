/* =========================================================
   NLU NHẸ (TF-IDF + Cosine Similarity)
   Đây KHÔNG phải sentence-transformers — nó là bản đối chứng
   (fallback) chạy hoàn toàn phía client khi không có mạng / chưa
   bật backend PHP + dịch vụ embedding. Cùng thuật toán được cài
   lại bằng PHP thuần trong backend/nlu/TfidfMatcher.php, và có
   thể nâng cấp lên embedding thật (sentence-transformers) — xem
   README mục "Nâng cấp NLU".
   ========================================================= */

const VN_STOPWORDS = new Set([
  "là","của","và","các","cho","theo","khi","nào","gì","có","bị","người","tôi",
  "em","anh","chị","mình","muốn","hỏi","xin","cho","hỏi","nếu","thì","đã",
  "sẽ","được","không","một","những","này","đó","với","về","trong","ra","lên",
  "làm","sao","như","thế","ạ","à","nhé","vậy","hay","hoặc","tại","bao","nhiêu"
]);

function vnNormalize(str){
  return str
    .toLowerCase()
    .normalize("NFD").replace(/[\u0300-\u036f]/g, "") // bỏ dấu để khớp linh hoạt hơn
    .replace(/đ/g, "d")
    .replace(/[^a-z0-9\s]/g, " ")
    .replace(/\s+/g, " ")
    .trim();
}

function tokenize(str){
  return vnNormalize(str).split(" ").filter(t => t && !VN_STOPWORDS.has(t));
}

/** Xây dựng chỉ mục TF-IDF cho một tập văn bản (mảng {id, text}) */
function buildIndex(docs){
  const df = new Map();
  const docTokens = docs.map(d => tokenize(d.text));
  docTokens.forEach(tokens => {
    new Set(tokens).forEach(t => df.set(t, (df.get(t) || 0) + 1));
  });
  const N = docs.length;
  const idf = new Map();
  df.forEach((count, term) => idf.set(term, Math.log((N + 1) / (count + 1)) + 1));

  const vectors = docTokens.map(tokens => {
    const tf = new Map();
    tokens.forEach(t => tf.set(t, (tf.get(t) || 0) + 1));
    const vec = new Map();
    tf.forEach((count, term) => vec.set(term, (count / tokens.length) * (idf.get(term) || 0)));
    return vec;
  });

  return { idf, vectors, docs };
}

function vectorize(text, idf){
  const tokens = tokenize(text);
  const tf = new Map();
  tokens.forEach(t => tf.set(t, (tf.get(t) || 0) + 1));
  const vec = new Map();
  tf.forEach((count, term) => {
    if (idf.has(term)) vec.set(term, (count / tokens.length) * idf.get(term));
  });
  return vec;
}

function cosineSim(a, b){
  let dot = 0, na = 0, nb = 0;
  a.forEach((v, k) => { na += v * v; if (b.has(k)) dot += v * b.get(k); });
  b.forEach(v => { nb += v * v; });
  if (na === 0 || nb === 0) return 0;
  return dot / (Math.sqrt(na) * Math.sqrt(nb));
}

/** Trả về top-k tài liệu gần nhất kèm điểm tương đồng [0..1] */
function semanticSearch(query, index, k = 5){
  const qVec = vectorize(query, index.idf);
  const scored = index.docs.map((d, i) => ({
    doc: d,
    score: cosineSim(qVec, index.vectors[i])
  }));
  scored.sort((a, b) => b.score - a.score);
  return scored.slice(0, k);
}
