#!/bin/bash

CONFIG_ETC='/etc/coffeecp.config'
PORTS_PER_USER=9

function enter_user_details(){
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
}

function setup_user_config(){
  JAVA_CONFIG="/home/${USERNAME}/java_${USERNAME}.json"

  #check if we have enough ports for allocation to user
  OUR_BASE_PORT=${FREED_PORTS##*,}     #take last freed port
  if [ "${OUR_BASE_PORT}" ]; then
    FREED_PORTS="${FREED_PORTS%,*}"  #drop last freed port
  elif [ $((BASE_PORT+PORTS_PER_USER)) -ge 65535 ]; then
    echo "Error: Not enough ports for user";
    exit 1;
  else
    OUR_BASE_PORT=${BASE_PORT}
    let BASE_PORT=BASE_PORT+PORTS_PER_USER
  fi

  if [ -f ${JAVA_CONFIG} ]; then
    sed -i.save "s/-Xmx[0-9]\+/-Xmx${MEM_VALUE}000000/" ${JAVA_CONFIG}
    sed -i.save "s/value\":\"[0-9]\+\"/value\":\"${MEM_VALUE}\"/" ${JAVA_CONFIG}

    #return old base port to freed ports
    HTTP_PORT=$(sed -n 's/\s\+"http":\([0-9]\+\),/\1/p' ${JAVA_CONFIG})
    FREED_PORTS="${FREED_PORTS},${HTTP_PORT}"
  fi

  if [ $(grep -c -m1 "#${USERNAME}=" ${CONFIG_ETC}) -eq 1 ]; then
    sed -i.save "s/#${USERNAME}=.*/#${USERNAME}=${OUR_BASE_PORT}/" ${CONFIG_ETC}
  else
    echo "#${USERNAME}=${OUR_BASE_PORT}" >> ${CONFIG_ETC}
  fi


  cat > ${JAVA_CONFIG} <<CMD_EOF
{
  "memory":"JAVA_OPTS=\"-Xmx${MEM_VALUE}000000 -Djava.awt.headless=true\"",
  "value":"${MEM_VALUE}",
  "ports":{
    "http":$((OUR_BASE_PORT)),
    "https":$((OUR_BASE_PORT+1)),
    "ajp_or_orb":$((OUR_BASE_PORT+2)),
    "shutdown_or_orb_ssl_or_mgt_http":$((OUR_BASE_PORT+3)),
    "orb_ssl_mu_or_mgt_https":$((OUR_BASE_PORT+4)),
    "jmx_conn_or_txt_reco_env":$((OUR_BASE_PORT+5)),
    "admin_http":$((OUR_BASE_PORT+6)),
    "jmx_host_or_txt_status":$((OUR_BASE_PORT+7)),
    "osgi_shell":$((OUR_BASE_PORT+8))
  }
}
CMD_EOF
}

function load_config(){
  if [ ! -f "${CONFIG_ETC}" ]; then
    cat >${CONFIG_ETC} << CMD_EOF
BASE_PORT=5100
FREED_PORTS=''
CMD_EOF
  fi

  DUPLICATES=$(sed -n 's/^\#.*=\([0-9]\+\)/\1/p' ${CONFIG_ETC} | sort | uniq -d)
  if [ "${DUPLICATES}" ]; then
    echo "Error: Duplicate ports detected: ${DUPLICATES}"
    exit 2;
  fi

  source "${CONFIG_ETC}"
}

function save_config(){
  #advance base port in config file
  sed -i.save "s/BASE_PORT=[0-9]\+/BASE_PORT=${BASE_PORT}/" ${CONFIG_ETC}
  sed -i.save "s/FREED_PORTS=[0-9]\+/FREED_PORTS=${FREED_PORTS}/" ${CONFIG_ETC}
}

read -p 'Enter username: ' USERNAME
read -p 'Enter domain: ' USER_DOMAIN

load_config;

enter_user_details;
setup_user_config;

save_config
