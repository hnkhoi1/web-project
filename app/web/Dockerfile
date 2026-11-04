FROM node:26-trixie-slim AS base
ENV PNPM_HOME=/pnpm PATH=/pnpm:$PATH
RUN npm i -g pnpm@12
WORKDIR /app

FROM base AS build
COPY package.json pnpm-lock.yaml ./
RUN pnpm install --frozen-lockfile
COPY . .
RUN pnpm build

FROM base AS dev
# Pre-create with the unprivileged owner so the named node_modules volume is writable.
RUN mkdir -p /app/node_modules /pnpm && chown -R 1000:1000 /app /pnpm
EXPOSE 5174
CMD ["sh", "-c", "pnpm install && pnpm dev --host 0.0.0.0 --port 5174"]

FROM nginx:stable-trixie AS prod
COPY --from=build /app/dist /usr/share/nginx/html
EXPOSE 80
