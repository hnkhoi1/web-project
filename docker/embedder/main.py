"""Small embedding service for multilingual-e5-small."""
from typing import Literal

import numpy as np
from fastapi import FastAPI
from pydantic import BaseModel, Field
from sentence_transformers import SentenceTransformer

MODEL_NAME = "intfloat/multilingual-e5-small"
MAX_TEXTS = 64

model = SentenceTransformer(MODEL_NAME, device="cpu")
app = FastAPI()


class EmbedRequest(BaseModel):
    texts: list[str] = Field(min_length=1, max_length=MAX_TEXTS)
    kind: Literal["query", "passage"]


def encode(texts: list[str]) -> np.ndarray:
    return model.encode(texts, normalize_embeddings=True, batch_size=16, show_progress_bar=False)


@app.get("/health")
def health() -> dict[str, str]:
    return {"status": "ok"}


# Plain def: FastAPI runs it in a worker thread so /health stays responsive.
@app.post("/embed")
def embed(req: EmbedRequest) -> dict:
    vectors = encode([f"{req.kind}: {t}" for t in req.texts])
    return {
        "model": MODEL_NAME,
        "dimension": int(vectors.shape[1]),
        "vectors": vectors.astype(float).tolist(),
    }
