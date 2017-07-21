DEBUG=true
wget="/usr/bin/wget"
wgetOptions="--dns-timeout=30"
timeout=10
zenity="/usr/bin/zenity"
infoFile="/run/initramfs/info"
python="/usr/bin/python"

# transmit state to server
function clientState()
{
  $DEBUG && \
    ${wget} ${wgetOptions} -qO- "${urlNotify//\{state\}/$1}" 1>&2 || \
    ${wget} ${wgetOptions} -qO- "${urlNotify//\{state\}/$1}" 2>&1 >/dev/null
  $DEBUG && >&2 echo "New client state: $1"
}

function config_value()
{
  if [ -n "${config}" ]; then
    config="$(${wget} ${wgetOptions} -qO- "${urlConfig}")"
    retval=$?
    if [ ${retval} -ne 0 ]; then
      >&2 echo "wget failed while fetching the system config (return value: ${retval})."
      ${zenity} --error --title "Wget error" --text "wget failed while fetching the system config (return value: ${retval})."
      do_exit
    fi
  fi

  v="$(echo "${config}" | ${python} -c 'import sys, json; print json.load(sys.stdin)["config"]["'${1}'"]')"
  $DEBUG && >&2 echo "${1} is set to ${v}"
  echo "$v"
}

export DISPLAY=:0

echo 0 > /run/initramfs/restore

token=$1
[ -r "${infoFile}" ] && . ${infoFile}
echo "token=${token}" >> "${infoFile}"

# replace the placeholder {token} in the URLs
urlDownload="${actionDownload//\{token\}/$token}"
urlFinish="${actionFinish//\{token\}/$token}"
urlNotify="${actionNotify//\{token\}/$token}"
urlMd5="${actionMd5//\{token\}/$token}"
urlConfig="${actionConfig//\{token\}/$token}"

# write the info file
cat <<EOF >>"${infoFile}"
urlDownload="${urlDownload}"
urlFinish="${urlFinish}"
urlNotify="${urlNotify}"
urlMd5="${urlMd5}"
urlConfig="${urlConfig}"
EOF

# create necessary directory structure
mkdir -p "/run/initramfs/backup/etc/NetworkManager/"{system-connections,dispatcher.d}
mkdir -p "/run/initramfs/backup/home/user/Schreibtisch/"
mkdir -p "/run/initramfs/backup/usr/bin/"
mkdir -p "/run/initramfs/backup/usr/sbin/"
mkdir -p "/run/initramfs/backup/etc/live/config/"
mkdir -p "/run/initramfs/backup/etc/lernstick-firewall/"
mkdir -p "/run/initramfs/backup/etc/avahi/"
mkdir -p "/run/initramfs/backup/root/.ssh"

# set proper permissions
chown user:user "/run/initramfs/backup/home/user/Schreibtisch/"
chown user:user "/run/initramfs/backup/home/user/"
chmod 755 "/run/initramfs/backup/root"
chmod 700 "/run/initramfs/backup/root/.ssh"

# get all active network connections
con=$(LC_ALL=C nmcli -t -f state,connection d status | awk -F: '$1=="connected"{print $2}')
echo "${con}" | LC_ALL=C xargs -I{} cp -p "/etc/NetworkManager/system-connections/{}" "/run/initramfs/backup/etc/NetworkManager/system-connections/"

# edit copied connections manually, because nmcli will remove the wifi-sec.psk password when edited by nmcli modify
#sed -i '/\[connection\]/a permissions=user:root:;' /run/initramfs/backup/etc/NetworkManager/system-connections/*

# copy needed scripts and files
cp -p "/etc/NetworkManager/dispatcher.d/02searchExamServer" "/run/initramfs/backup/etc/NetworkManager/dispatcher.d/02searchExamServer"
cp -p "/usr/bin/finishExam" "/run/initramfs/backup/usr/bin/finishExam"

# those should be removed as fast as possible
cp -p "/usr/bin/lernstick_backup" "/run/initramfs/backup/usr/bin/lernstick_backup" #TODO: remove
cp -p "/usr/bin/lernstick_autostart" "/run/initramfs/backup/usr/bin/lernstick_autostart" #TODO: remove
cp -p "/usr/sbin/lernstick-firewall" "/run/initramfs/backup/usr/sbin/lernstick-firewall" #TODO: remove
cp -p "/etc/lernstick-firewall/lernstick-firewall.conf" "/run/initramfs/backup/etc/lernstick-firewall/lernstick-firewall.conf" #TODO: remove

cp -p "/etc/lernstickWelcome" "/run/initramfs/backup/etc/lernstickWelcome"
sed -i 's/ShowNotUsedInfo=.*/ShowNotUsedInfo=false/g' "/run/initramfs/backup/etc/lernstickWelcome"
sed -i 's/AutoStartInstaller=.*/AutoStartInstaller=false/g' "/run/initramfs/backup/etc/lernstickWelcome"
echo "ShowExamInfo=true" >>"/run/initramfs/backup/etc/lernstickWelcome" #TODO: replace with sed
cp -p "/usr/share/applications/finish_exam.desktop" "/run/initramfs/backup/home/user/Schreibtisch/"
chown user:user "/run/initramfs/backup/home/user/Schreibtisch/finish_exam.desktop"

# This is to fix an issue when the DNS name of the exam server end in .local (which is the case in most Microsoft
# domain environments). In case if a .local name the mDNS policy in /etc/nsswitch.conf will catch. This ends in ssh
# login delays of up to 20 seconds. Changing it to .alocal is a workaround. Better is not to use mDNS in an exam.
sed 's/#domain-name=local/domain-name=.alocal/' /etc/avahi/avahi-daemon.conf >/run/initramfs/backup/etc/avahi/avahi-daemon.conf

# apply specific config if available
mount /lib/live/mount/medium/live/filesystem.squashfs /run/initramfs/base
mount /run/initramfs/squashfs/exam.squashfs /run/initramfs/exam
mount -t aufs -o br=/run/initramfs/backup=rw:/run/initramfs/exam=ro:/run/initramfs/base=ro none "/run/initramfs/newroot"

# remove policykit action for lernstick welcome application
rm -f /run/initramfs/newroot/usr/share/polkit-1/actions/ch.lernstick.welcome.policy

if [ -n "${actionConfig}" ]; then
  # get the config
  config="$(${wget} ${wgetOptions} -qO- "${urlConfig}")"
  retval=$?
  if [ ${retval} -ne 0 ]; then
    >&2 echo "wget failed while fetching the system config (return value: ${retval})."
    ${zenity} --error --title "Wget error" --text "wget failed while fetching the system config (return value: ${retval})."
    do_exit
  fi

  # config->grp_netdev
  if [ "$(config_value "grp_netdev")" = "False" ]; then
    chroot /run/initramfs/newroot gpasswd -d user netdev
    sed -i 's/netdev//' /run/initramfs/newroot/etc/live/config/user-setup.conf
  else
    chroot /run/initramfs/newroot gpasswd -a user netdev
  fi

  # config->allow_sudo
  if [ "$(config_value "allow_sudo")" = "False" ]; then
    sed '/user  ALL=(ALL) PASSWD: ALL/ s/^/#/' /etc/sudoers >/run/initramfs/backup/etc/sudoers
  else
    sed '/^#user  ALL=(ALL) PASSWD: ALL/ s/^#//' /etc/sudoers >/run/initramfs/backup/etc/sudoers
  fi

  # config->allow_sudo
  if [ "$(config_value "allow_mount")" = "False" ]; then
    chroot /run/initramfs/newroot sed -i 's/^ResultAny=.*/ResultAny=auth_admin/;s/^ResultInactive=.*/ResultInactive=auth_admin/;s/^ResultActive=.*/ResultActive=auth_admin/' /etc/polkit-1/localauthority/50-local.d/10-udisks2.pkla
  else
    chroot /run/initramfs/newroot sed -i 's/^ResultAny=.*/ResultAny=yes/;s/^ResultInactive=.*/ResultInactive=yes/;s/^ResultActive=.*/ResultActive=yes/' /etc/polkit-1/localauthority/50-local.d/10-udisks2.pkla
  fi

  # config->firewall_off
  if [ "$(config_value "firewall_off")" = "False" ]; then
    chroot /run/initramfs/newroot systemctl enable lernstick-firewall.service
  else
    chroot /run/initramfs/newroot systemctl disable lernstick-firewall.service
  fi

  # config->screenshots
  if [ "$(config_value "screenshots")" = "False" ]; then
    chroot /run/initramfs/newroot sed -i 's/BackupScreenshot=.*/BackupScreenshot=false/' /etc/lernstickWelcome 
  else
    chroot /run/initramfs/newroot sed -i 's/BackupScreenshot=.*/BackupScreenshot=true/' /etc/lernstickWelcome
    chroot /run/initramfs/newroot sed -i 's/Backup=.*/Backup=true/' /etc/lernstickWelcome
  fi

  # config->url_whitelist
  if [ "$(config_value "url_whitelist")" != "" ]; then
    #wh="$(config_value "url_whitelist")"
    #echo "${wh}" | tee -a /run/initramfs/newroot/etc/lernstick-firewall/url_whitelist
    config_value "url_whitelist" | tee -a /run/initramfs/newroot/etc/lernstick-firewall/url_whitelist
  fi


else
  # these are the default values, if the exam server does not provide a config file and the exam file has not configured them
  $DEBUG && >&2 echo "no config available, setting default values"

  # remove user from the netdev group to prevent him from changing network connections
  chroot /run/initramfs/newroot gpasswd -d user netdev
  sed -i 's/netdev//' /run/initramfs/newroot/etc/live/config/user-setup.conf

  # remove sudo privileges
  sed '/user  ALL=(ALL) PASSWD: ALL/ s/^/#/' /etc/sudoers >/run/initramfs/backup/etc/sudoers

  # prevent user from mounting external media
  chroot /run/initramfs/newroot sed -i 's/^ResultAny=.*/ResultAny=auth_admin/;s/^ResultInactive=.*/ResultInactive=auth_admin/;s/^ResultActive=.*/ResultActive=auth_admin/' /etc/polkit-1/localauthority/50-local.d/10-udisks2.pkla

  # enable the firewall
  chroot /run/initramfs/newroot systemctl enable lernstick-firewall.service

fi

# hand over the ssh key from the exam server
echo "${sshKey}" >>"/run/initramfs/backup/root/.ssh/authorized_keys"

# hand over open ports
echo "tcp ${gladosIp} 22" >>/run/initramfs/backup/etc/lernstick-firewall/net_whitelist_input
echo "${gladosProto}://${gladosIp}:${gladosPort}" >>/run/initramfs/backup/etc/lernstick-firewall/url_whitelist
echo "tcp ${gladosIp} ${gladosPort}" >>/run/initramfs/backup/etc/lernstick-firewall/net_whitelist
sort -u -o /run/initramfs/backup/etc/lernstick-firewall/url_whitelist /run/initramfs/backup/etc/lernstick-firewall/url_whitelist

if $DEBUG; then
  if ${zenity} --question --title="Continue" --text="The system setup is done. Continue?"; then
    clientState "continue bootup"
    halt
  fi
else
  # timeout for 10 seconds
  for i in {1..10}; do
    echo "${i}0"
    echo "#The system will continue in $((10 - $i)) seconds"
    sleep 1
  done | ${zenity} --progress --no-cancel --title="Continue" --text="The system will continue in 10 seconds" --percentage=0 --auto-close
  clientState "continue bootup"
  halt
fi
