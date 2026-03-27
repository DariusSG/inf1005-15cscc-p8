# frontend/Dockerfile

FROM nginx:alpine

# Remove default config first (rarely changes)
RUN rm /etc/nginx/conf.d/default.conf

# Copy Nginx config (changes infrequently)
COPY frontend/docker/nginx.conf /etc/nginx/conf.d/default.conf

# Copy static HTML files (changes less frequently than src)
COPY frontend/index.html /usr/share/nginx/html/

# Copy frontend source (most frequently changing)
COPY frontend/src /usr/share/nginx/html/src/

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]