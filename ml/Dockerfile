FROM python:3.12-alpine

RUN apk add --no-cache gcc g++ gfortran musl-dev mariadb-connector-c-dev openblas-dev

WORKDIR /app
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY . .

EXPOSE 5001
CMD ["python", "app.py"]
