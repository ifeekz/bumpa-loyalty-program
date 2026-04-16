# Stage 1: Build
# Uses Node to install dependencies and compile the Vite app into static files.
# This stage is discarded after the build — nothing from node_modules or source
# code makes it into the final image.
FROM node:20-alpine AS builder

# Install pnpm
RUN corepack enable && corepack prepare pnpm@latest --activate

WORKDIR /app

# Copy lockfile + manifest first to leverage Docker layer caching.
# Dependencies are only re-installed when these two files change.
COPY package.json pnpm-lock.yaml ./

RUN pnpm install --frozen-lockfile

# Copy source and build
COPY . .

# Build args let CI/CD inject environment variables at build time.
# VITE_* variables are embedded into the static bundle at build time —
# they are NOT available at runtime like Node env vars.
ARG VITE_API_URL=/api
ENV VITE_API_URL=$VITE_API_URL

RUN pnpm run build

# Stage 2: Serve
# Nginx serves the compiled static files.
# Final image is ~25MB vs ~400MB if we shipped Node + source.
FROM nginx:1.25-alpine AS runner

# Remove the default Nginx config
RUN rm /etc/nginx/conf.d/default.conf

# Copy our custom Nginx config
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf

# Copy compiled assets from the builder stage
COPY --from=builder /app/dist /usr/share/nginx/html

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
