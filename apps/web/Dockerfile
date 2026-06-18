FROM node:20-alpine AS base

RUN corepack enable && corepack prepare pnpm@10.12.1 --activate

FROM base AS deps
WORKDIR /app
COPY package.json pnpm-lock.yaml ./
RUN pnpm install --frozen-lockfile

FROM base AS builder
WORKDIR /app
COPY --from=deps /app/node_modules ./node_modules
COPY . .

ARG NEXT_PUBLIC_API_URL
ARG NEXT_PUBLIC_MERCURE_URL
ARG NEXT_PUBLIC_UMAMI_URL
ARG NEXT_PUBLIC_UMAMI_WEBSITE_ID
ARG NEXT_PUBLIC_SENTRY_DSN
ENV NEXT_PUBLIC_API_URL=$NEXT_PUBLIC_API_URL
ENV NEXT_PUBLIC_MERCURE_URL=$NEXT_PUBLIC_MERCURE_URL
ENV NEXT_PUBLIC_UMAMI_URL=$NEXT_PUBLIC_UMAMI_URL
ENV NEXT_PUBLIC_UMAMI_WEBSITE_ID=$NEXT_PUBLIC_UMAMI_WEBSITE_ID
ENV NEXT_PUBLIC_SENTRY_DSN=$NEXT_PUBLIC_SENTRY_DSN

RUN pnpm build

FROM base AS runner
WORKDIR /app

ENV NODE_ENV=production

COPY --from=builder /app/public ./public
COPY --from=builder /app/.next ./.next
COPY --from=builder /app/node_modules ./node_modules
COPY --from=builder /app/package.json ./package.json

EXPOSE 3000

CMD ["sh", "-c", "pnpm start --hostname 0.0.0.0 --port ${PORT:-3000}"]
