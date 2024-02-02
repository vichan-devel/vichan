FROM nginx:1.25.3-alpine

COPY . /code
RUN /code/docker/common-setup.sh


CMD ["nginx", "-g", "daemon off;"]
EXPOSE 80 443