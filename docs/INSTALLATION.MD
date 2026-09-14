(base) christian@christian:~/Programming/express-sale$ docker compose cp public.sql db:/tmp/public.sql
WARN[0000] /home/christian/Programming/express-sale/docker-compose.yml: the attribute `version` is obsolete, it will be ignored, please remove it to avoid potential confusion 
[+] copy 1/1
 ✔ express_db Copied public.sql to express_db:/tmp/public.sql                                                                                                                      0.0s 
(base) christian@christian:~/Programming/express-sale$ docker compose exec -it db psql -U express_sale_user -d express_sale_db -f /tmp/public.sql
WARN[0000] /home/christian/Programming/express-sale/docker-compose.yml: the attribute `version` is obsolete, it will be ignored, please remove it to

 
sudo nano /etc/udev/rules.d/99-xprinter.rules





# Xprinter - STMicroelectronics USB Printer Port
# Vendor ID: 0483, Product ID: 5743
SUBSYSTEM=="usb", ATTRS{idVendor}=="0483", ATTRS{idProduct}=="5743", MODE="0666", GROUP="lp"

# Alternative plus permissive (si la règle au-dessus ne fonctionne pas)
KERNEL=="lp[0-9]*", MODE="0666", GROUP="lp"




sudo udevadm control --reload-rules
sudo udevadm trigger
il faut d abord copier les images. 
ls -l /dev/usb/lp0
