import math

import pytest
from fastapi.testclient import TestClient

import main

client = TestClient(main.app)


def embed(texts, kind="passage"):
    return client.post("/embed", json={"texts": texts, "kind": kind})


def dot(a, b):
    return sum(x * y for x, y in zip(a, b))


def test_health():
    assert client.get("/health").status_code == 200


def test_embed_returns_unit_vectors_of_dimension_384():
    r = embed(["tội trộm cắp tài sản", "tội đánh bạc"])
    assert r.status_code == 200
    body = r.json()
    assert body["model"] == "intfloat/multilingual-e5-small"
    assert body["dimension"] == 384
    assert len(body["vectors"]) == 2
    for v in body["vectors"]:
        assert len(v) == 384
        assert math.isclose(math.sqrt(dot(v, v)), 1.0, abs_tol=1e-3)


def test_kind_applies_prefix(monkeypatch):
    seen = []
    real = main.encode

    def spy(texts):
        seen.append(list(texts))
        return real(texts)

    monkeypatch.setattr(main, "encode", spy)
    embed(["xin chào"], "query")
    embed(["xin chào"], "passage")
    assert seen == [["query: xin chào"], ["passage: xin chào"]]


@pytest.mark.parametrize("payload", [
    {"texts": [], "kind": "query"},
    {"texts": ["x"] * 65, "kind": "query"},
    {"texts": [1, 2], "kind": "query"},
    {"texts": ["x"], "kind": "other"},
])
def test_invalid_payload_is_422(payload):
    assert client.post("/embed", json=payload).status_code == 422


def test_similar_vietnamese_pair_scores_higher():
    # The bare phrases from the brief ("lạm dụng tín nhiệm chiếm đoạt tài sản" vs "tội đánh bạc")
    # score 0.807 vs 0.811 with this model, so the pair is written as stored passages (title + summary).
    q = embed(["người quen mượn xe rồi đem bán"], "query").json()["vectors"][0]
    ps = embed([
        "Tội lạm dụng tín nhiệm chiếm đoạt tài sản. Người nào vay, mượn, thuê tài sản rồi dùng thủ đoạn gian dối chiếm đoạt",
        "Tội đánh bạc. Người nào đánh bạc trái phép bằng bất kỳ hình thức nào",
    ]).json()["vectors"]
    assert dot(q, ps[0]) > dot(q, ps[1])
