#!/usr/bin/env bash

for i in `seq 1 100`; do
    ./codecept g:test unit Stub/Test$i
done

for i in `seq 101 200`; do
    ./codecept g:test api Stub/Test$i
done