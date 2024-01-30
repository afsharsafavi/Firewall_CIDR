FROM php:8.2-cli
COPY . /myapp
WORKDIR /myapp
CMD [ "php", "./Examples/example1.php" ]