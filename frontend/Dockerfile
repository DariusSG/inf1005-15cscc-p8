# frontend/Dockerfile

FROM nginx:alpine

# Remove default config
RUN rm /etc/nginx/conf.d/default.conf

# Add config
COPY frontend/docker/nginx.conf /etc/nginx/conf.d/default.conf

# Copy static files
COPY frontend/index.html /usr/share/nginx/html
COPY frontend/src /usr/share/nginx/html/src

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]