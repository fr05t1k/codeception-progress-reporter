#!/usr/bin/env sh

for i in `seq 1 100`; do
     php vendor/bin/codecept g:test unit Stub/Test$i
done

for i in `seq 101 200`; do
     php vendor/bin/codecept g:test api Stub/Test$i
done