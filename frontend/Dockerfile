# ---- Stage 1: Build ----
FROM node:20-alpine AS builder

WORKDIR /app

# Copy package files first (for caching)
COPY package.json pnpm-lock.yaml ./

# Install pnpm
RUN npm install -g pnpm

# Install dependencies
RUN pnpm install

# Copy the rest of the app
COPY . .

# Build the Vite app
RUN pnpm build


# ---- Stage 2: Serve ----
FROM nginx:alpine

# Remove default nginx static files
RUN rm -rf /usr/share/nginx/html/*

# Copy built files from builder
COPY --from=builder /app/dist /usr/share/nginx/html

# Copy custom nginx config
COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]