#!/bin/bash

CONFIG_ETC='/etc/coffeecp.config'

function enter_user_details(){
  read -p 'Enter username: ' USERNAME
  read -p 'Enter domain: ' USER_DOMAIN

  options=(64 128 256 512 1024 2048 4096)

  PS3="Select memory size (in MB): "
  select opt in "${options[@]}" "Quit"; do
    case "$REPLY" in
      [1-7])
        MEM_VALUE=${options[${REPLY}-1]}
        break;;
      *)
        echo "Invalid option. Try another one.";
      continue;;
    esac
  done

  JAVA_CONFIG="/home/${USERNAME}/java_${USERNAME}.json"
  sed -i.save "s/-Xmx[0-9]\+/-Xmx${MEM_VALUE}000000/" ${JAVA_CONFIG}
  sed -i.save "s/value\":\"[0-9]\+\"/value\":\"${MEM_VALUE}\"/" ${JAVA_CONFIG}
}

function update_appservers(){
  #for each tomcat instance
  for tomcat_dir in $(ls -d /home/${USERNAME}/appservers/apache-tomcat-*/); do
    sed -i.save "/^export JAVA_OPTS=/s/-Xmx[0-9]\+/-Xmx${MEM_VALUE}000000/" ${tomcat_dir}/bin/catalina.sh
    rm -f ${tomcat_dir}/bin/catalina.sh.save
  done
}

enter_user_details;
update_appservers;
