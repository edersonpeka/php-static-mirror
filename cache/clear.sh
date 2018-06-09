#!/bin/bash
cd "${0%/*}"
find . -name "*.txt" -mtime +10 -type f -delete
