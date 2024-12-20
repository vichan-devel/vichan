FROM nginx:1.25.3-alpine

COPY . /code
RUN adduser --system www-data \
  && adduser www-data www-data

CMD [ "nginx", "-g", "daemon off;" ]
EXPOSE 80
