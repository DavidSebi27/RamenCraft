FROM node:20-alpine

WORKDIR /app

# Install dependencies first (cached layer)
COPY package.json package-lock.json ./
RUN npm ci

# Copy the rest of the frontend source
COPY . .

# Vite dev server on port 5173, accessible from outside the container
EXPOSE 5173
CMD ["npx", "vite", "--host", "0.0.0.0"]
