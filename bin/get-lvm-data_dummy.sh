#!/bin/sh

DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )

# PVS command...
cat "$DIR/../example-data-pvs.txt" 1>&3

# LVS command...
cat "$DIR/../example-data-lvs.txt" 1>&4
