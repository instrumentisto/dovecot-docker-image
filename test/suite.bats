#!/usr/bin/env bash


@test "post_push hook is up-to-date" {
  run sh -c "cat Makefile | grep $DOCKERFILE: \
                          | cut -d ':' -f 2 \
                          | cut -d '\\' -f 1 \
                          | tr -d ' '"
  [ "$status" -eq 0 ]
  [ "$output" != '' ]
  expected="$output"

  run sh -c "cat '$DOCKERFILE/hooks/post_push' \
               | grep 'for tag in' \
               | cut -d '{' -f 2 \
               | cut -d '}' -f 1"
  [ "$status" -eq 0 ]
  [ "$output" != '' ]
  actual="$output"

  [ "$actual" == "$expected" ]
}


@test "dovecot runs ok" {
  run docker run --rm --entrypoint sh $IMAGE -c 'dovecot --version'
  [ "$status" -eq 0 ]
}

@test "dovecot has correct version" {
  run docker run --rm --entrypoint sh $IMAGE -c \
    "dovecot --version | cut -d ' ' -f 1 \
                       | tr -d ' '"
  [ "$status" -eq 0 ]
  [ "$output" != '' ]
  actual="$output"

  run sh -c "cat Makefile | grep $DOCKERFILE: \
                          | cut -d ':' -f 2 \
                          | cut -d ',' -f 1 \
                          | cut -d '-' -f 1 \
                          | tr -d ' '"
  [ "$status" -eq 0 ]
  [ "$output" != '' ]
  expected="$output"

  [ "$actual.0" == "$expected" ]
}


@test "errors are logged to STDERR" {
  run docker run --rm --entrypoint sh $IMAGE -c \
    'doveconf | grep -Fx "log_path = /dev/stderr"'
  [ "$status" -eq 0 ]
}

@test "info logs are logged to STDOUT" {
  run docker run --rm --entrypoint sh $IMAGE -c \
    'doveconf | grep -Fx "info_log_path = /dev/stdout"'
  [ "$status" -eq 0 ]
}

@test "debug logs are logged to STDOUT" {
  run docker run --rm --entrypoint sh $IMAGE -c \
    'doveconf | grep -Fx "debug_log_path = /dev/stdout"'
  [ "$status" -eq 0 ]
}


@test "default SSL cert is configured correctly" {
  run docker run --rm --entrypoint sh $IMAGE -c \
    'doveconf | grep -Fx "ssl_cert = </etc/ssl/dovecot/server.pem"'
  [ "$status" -eq 0 ]
}

@test "default SSL cert is present" {
  run docker run --rm --entrypoint sh $IMAGE -c \
    'ls /etc/ssl/dovecot/server.pem'
  [ "$status" -eq 0 ]
}

@test "default SSL key is configured correctly" {
  run docker run --rm --entrypoint sh $IMAGE -c \
    'doveconf -P | grep -Fx "ssl_key = </etc/ssl/dovecot/server.key"'
  [ "$status" -eq 0 ]
}

@test "default SSL key is present" {
  run docker run --rm --entrypoint sh $IMAGE -c \
    'ls /etc/ssl/dovecot/server.key'
  [ "$status" -eq 0 ]
}

@test "default SSL key has correct rights" {
  run docker run --rm --entrypoint sh $IMAGE -c \
    'stat -c "%a" /etc/ssl/dovecot/server.key'
  [ "$status" -eq 0 ]
  [ "$output" == "600" ]
}
