FROM ubuntu:latest

RUN apt-get update && apt-get install -y curl
RUN curl https://cursor.com/install -fsS | bash -

ENV PATH="/root/.local/bin:$PATH"
