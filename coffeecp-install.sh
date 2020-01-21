#!/bin/bash -e
# CoffeeCP Installation Script
# For use on CentOS 6 or 7 with WHM/cPanel
# Cited, Inc. Wilmington, Delaware


# Main function

# Create directories and download files
function install_coffeecp(){
    if [ ! -f '/tmp/coffeecp.zip' ]; then
      wget -P/tmp 'https://files.coffeecp.com/coffeecp/cpanel/packs/coffeecp.zip'
    fi

# Create the main directory

    mkdir -p /usr/coffeecp

    pushd /usr/coffeecp;
      unzip -o /tmp/coffeecp.zip
      mv coffeecp_* /usr/local/cpanel/base/frontend/paper_lantern
      rm -f /tmp/coffeecp.zip

      pushd /usr/coffeecp/scripts
        chmod +x coffeecp_build_json.sh coffeecp_jdks.sh config-helper.sh
        ./coffeecp_build_json.sh
      popd
    popd

# Install cPanel Mods

    pushd /usr/local/cpanel/base/frontend/paper_lantern
      tar cj --overwrite -f coffeecp_master.tar.bz2 coffeecp_master
      /usr/local/cpanel/scripts/install_plugin /usr/local/cpanel/base/frontend/paper_lantern/coffeecp_master.tar.bz2
    popd

# Create cron entries

    if [ $(grep -c -m 1 'coffeecp_build_json.sh' /var/spool/cron/root) -eq 0 ]; then
    cat >>/var/spool/cron/root <<CMD_EOF
*/15 * * * * /usr/coffeecp/scripts/coffeecp_build_json.sh   > /dev/null 2>&1
*/15 * * * * /usr/coffeecp/scripts/config-helper.sh > /dev/null 2>&1
CMD_EOF
  fi

# Restart cron service
  service crond restart

# Create default entry for admin access

  mkdir -p /usr/local/apache/conf/coffeecppaths/
  cat >/usr/local/apache/conf/coffeecppaths/coffeecp.inc <<CMD_EOF
Listen *:9990
NameVirtualHost *:9990
Listen *:4848
NameVirtualHost *:4848
CMD_EOF

  if [ $(grep -c 'coffeecppaths/coffeecp.inc' /etc/apache2/conf.d/includes/post_virtualhost_global.conf) -eq 0 ]; then
    echo 'Include /usr/local/apache/conf/coffeecppaths/coffeecp.inc' >> /etc/apache2/conf.d/includes/post_virtualhost_global.conf
  fi
}

# Install Oracle JDKs and Create JSON files

function install_jdks(){

    if [ ! -f /tmp/jdks.zip ]; then
      wget -P/tmp/ 'https://files.coffeecp.com/coffeecp/cpanel/jdks.zip'
    fi

    mkdir -p /usr/java
    pushd /usr/java
      unzip /tmp/jdks.zip
      rm -f /tmp/jdks.zip

      tar --overwrite -vxf *.tar.gz
      rm -f *.tar.gz
    popd



cat >/tmp/new_func.sh <<VAR_END
FINAL_JSON+=',"JDK": ['

DELIMITER=''
for f in $(ls -d /usr/lib/jvm/jre-[0-9\.]*-openjdk); do
  version=$(echo ${f} | sed 's/.*jre-\([0-9\.]\+\)-openjdk$/\1/')
  FINAL_JSON+="${DELIMITER}{\"version\":\"${version}\", \"name\":\"OpenJDK\", \"path\":\"/usr/lib/jvm/jre-${version}-openjdk\"}"
  DELIMITER=','
done

FINAL_JSON+=']'
return 0;
VAR_END

    sed -i.save "/FINAL_JSON+=',\"JDK\"/,/return 0;/d" /usr/coffeecp/scripts/coffeecp_build_json.sh
    sed -i.save "/build_jdk_list(){/r /tmp/new_func.sh" /usr/coffeecp/scripts/coffeecp_build_json.sh
    rm -f /tmp/new_func.sh
    chmod +x /usr/coffeecp/scripts/coffeecp_build_json.sh
    sh /usr/coffeecp/scripts/coffeecp_build_json.sh


}

# Install OpenJDKs and Create JSON files

function install_openjdks(){
    yum install -y java-{1.7.0,1.8.0,11}-openjdk-headless

    cat >/tmp/new_func.sh <<VAR_END
FINAL_JSON+=',"JDK": ['

DELIMITER=''
for f in $(ls -d /usr/lib/jvm/jre-[0-9\.]*-openjdk); do
  version=$(echo ${f} | sed 's/.*jre-\([0-9\.]\+\)-openjdk$/\1/')
  FINAL_JSON+="${DELIMITER}{\"version\":\"${version}\", \"name\":\"OpenJDK\", \"path\":\"/usr/lib/jvm/jre-${version}-openjdk\"}"
  DELIMITER=','
done

FINAL_JSON+=']'
return 0;
VAR_END

    sed -i.save "/FINAL_JSON+=',\"JDK\"/,/return 0;/d" /usr/coffeecp/scripts/coffeecp_build_json.sh
    sed -i.save "/build_jdk_list(){/r /tmp/new_func.sh" /usr/coffeecp/scripts/coffeecp_build_json.sh
    rm -f /tmp/new_func.sh
    chmod +x /usr/coffeecp/scripts/coffeecp_build_json.sh
    sh /usr/coffeecp/scripts/coffeecp_build_json.sh
}

install_coffeecp;
#install_jdks; #For Oracle JDK only
install_openjdks;
