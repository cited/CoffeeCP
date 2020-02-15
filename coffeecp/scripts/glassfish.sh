#!/bin/bash  
GLASSFISH_HOME=#GLASSFISH_PATH#
  
case $1 in  
start)  
sh $GLASSFISH_HOME/bin/asadmin start-domain domain1 
;;  
stop)  
sh $GLASSFISH_HOME/bin/asadmin stop-domain domain1 
;;  
restart)  
sh $GLASSFISH_HOME/bin/asadmin stop-domain domain1
sh $GLASSFISH_HOME/bin/asadmin start-domain domain1
;;  
esac  
exit 0  