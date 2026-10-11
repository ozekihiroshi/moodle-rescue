# syntax=docker/dockerfile:1
# CI/local S3 fixture: build the archived upstream sources, not a registry mirror.
FROM golang:1.25-bookworm AS server-build
ADD --checksum=sha256:45521908307306e925c98d629e1c17d78c8b72b6ee242b1bfb1409f7d8ee5841 https://codeload.github.com/minio/minio/tar.gz/9e49d5e7a648f00e26f2246f4dc28e6b07f8c84a /tmp/minio.tar.gz
RUN mkdir /src && tar -xzf /tmp/minio.tar.gz -C /src --strip-components=1
WORKDIR /src
RUN CGO_ENABLED=0 go build -trimpath -o /out/minio .

FROM golang:1.25-bookworm AS client-build
ADD --checksum=sha256:95cd293c7119f16921a6dc515a1fb74a2227f19fd994b9c8b770a154e802ac44 https://codeload.github.com/minio/mc/tar.gz/7394ce0dd2a80935aded936b09fa12cbb3cb8096 /tmp/mc.tar.gz
RUN mkdir /src && tar -xzf /tmp/mc.tar.gz -C /src --strip-components=1
WORKDIR /src
RUN CGO_ENABLED=0 go build -trimpath -o /out/mc .

FROM debian:bookworm-slim
RUN apt-get update && apt-get install -y --no-install-recommends ca-certificates curl \
    && rm -rf /var/lib/apt/lists/*
COPY --from=server-build /out/minio /usr/local/bin/minio
COPY --from=client-build /out/mc /usr/local/bin/mc
COPY --from=server-build /src/LICENSE /usr/share/doc/minio/LICENSE
COPY --from=client-build /src/LICENSE /usr/share/doc/mc/LICENSE
LABEL org.opencontainers.image.title="Moodle local MinIO test fixture" \
      org.opencontainers.image.licenses="AGPL-3.0-only" \
      io.moodle-rescue.minio.revision="9e49d5e7a648f00e26f2246f4dc28e6b07f8c84a" \
      io.moodle-rescue.mc.revision="7394ce0dd2a80935aded936b09fa12cbb3cb8096"
EXPOSE 9000 9001
ENTRYPOINT ["minio"]
CMD ["server", "/data", "--console-address", ":9001"]
