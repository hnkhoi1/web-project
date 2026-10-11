document.addEventListener("DOMContentLoaded", () => {
  const page = document.body.dataset.page;
  document.querySelectorAll("nav.main-nav a[data-page]").forEach(a => {
    if (a.dataset.page === page) a.classList.add("active");
  });
});

/** Gọi API backend PHP; nếu không có server (demo tĩnh / mở file trực tiếp)
 *  thì báo lỗi để nơi gọi tự chuyển sang xử lý NLU phía client (nlu.js). */
async function callApi(path, options = {}) {
  const res = await fetch(path, options);
  if (!res.ok) throw new Error("API lỗi: " + res.status);
  return res.json();
}
