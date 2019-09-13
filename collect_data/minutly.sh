#!/bin/bash
#export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"
frequency="minutly"
base_path="/volume1/config_scripte/collect_data"
for file in ${base_path}/${frequency}/*.sh
do
    sh ${file} >> ${base_path}_log_${frequency}.txt 2>&1
done
