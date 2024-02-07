FROM nginx:1.25.3-alpine

COPY . /code
RUN addgroup --system leftypol \
   && adduser --system leftypol \
   && adduser leftypol leftypol \
   && /code/docker/common-setup.sh


CMD ["nginx", "-g", "daemon off;"]
EXPOSE 80 443