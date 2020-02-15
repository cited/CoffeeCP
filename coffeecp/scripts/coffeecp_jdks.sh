#!/bin/bash -e
 
function prepare_jdks ()
{
COFFEECP_DIR='/usr/java'
pushd ${COFFEECP_DIR}
for f in jdk-*-linux-x64.tar.gz; do
tar xzf ${f}
rm -f ${f}
done
popd

}

prepare_jdks;