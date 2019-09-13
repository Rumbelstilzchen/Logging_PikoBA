#!/bin/bash
#export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"
frequency="hourly"
base_path="/volume1/config_scripte/collect_data"
rm -rf ${base_path}_log_${frequency}.txt

for file in ${base_path}/${frequency}/*.sh
do
	date >> ${base_path}_log_${frequency}.txt
	echo ${file} >> ${base_path}_log_${frequency}.txt
    sh ${file} >> ${base_path}_log_${frequency}.txt 2>&1
	echo >> ${base_path}_log_${frequency}.txt
done
date >> ${base_path}_log_${frequency}.txt
