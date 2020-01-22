# CoffeeCP - Java for cPanel

![CoffeeCP](https://www.acugis.com/coffeecp/assets/img/logo.jpg)



Provision Tomcat, GlassFish, and WildFly on cPanel instantly. 

Users access all components via cPanel. No extra control panels to install. 

All customer-facing components can be customized on a per-server basis to suit your own brand and business needs. Use our pre-made templates or create your own.

Built with PHP, JSON, and Perl, our modules have a tiny footprint.   <br /><br />




## Install CoffeeCP:

 

SSH to your server as root and issue the following commands.

 

wget https://raw.githubusercontent.com/cited/CoffeeCP/master/coffeecp-install.sh

chmod +x coffeecp_install.sh

./coffeecp_install.sh <br /><br />

 
## Add Users:

 

wget https://raw.githubusercontent.com/cited/CoffeeCP/blob/master/coffeecp-create-user.sh

chmod +x coffeecp_create_user.sh

./coffeecp_create_user.sh


 

You will be prompted for the username, domain, and the amount of heap space (memory) you wish to allot the user. <br /><br />

 
## Update User:

 

wget https://raw.githubusercontent.com/cited/CoffeeCP/blob/master/coffeecp-update-user.sh

chmod +x coffeecp_update_user.sh

./coffeecp_update_user.sh

 

You will be prompted for the username, domain, and the amount of heap space (memory) you wish to allot the user. <br /><br />

 

For full documentation, see our *[Docs](https://www.acugis.com/coffeecp/docs/)*.

&copy; Cited, Inc. 2017-2020
