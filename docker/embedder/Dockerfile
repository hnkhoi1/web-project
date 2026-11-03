FROM python:3.12-slim AS runtime

ENV PIP_NO_CACHE_DIR=1 PYTHONUNBUFFERED=1 HF_HOME=/opt/hf
WORKDIR /app

# CPU-only torch first so sentence-transformers does not pull the CUDA build.
RUN pip install --index-url https://download.pytorch.org/whl/cpu torch==2.15.0
COPY requirements.txt .
RUN pip install -r requirements.txt

# Bake the model into the image; runtime never touches the network.
RUN python -c "from sentence_transformers import SentenceTransformer as S; S('intfloat/multilingual-e5-small')"
ENV HF_HUB_OFFLINE=1

COPY main.py .
EXPOSE 8000
HEALTHCHECK --interval=10s --timeout=5s --start-period=60s --retries=5 \
    CMD python -c "import urllib.request as u; u.urlopen('http://127.0.0.1:8000/health', timeout=4)"
CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8000", "--workers", "1"]

FROM runtime AS test
COPY requirements-test.txt test_main.py ./
RUN pip install -r requirements-test.txt
CMD ["pytest", "-q"]
