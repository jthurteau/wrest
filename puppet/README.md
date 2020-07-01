This folder contains provisioning tools for standing up a VM to develop against SAF.

The MrRogers provisioner requires Vagrant and VirtualBox
https://www.vagrantup.com/
https://www.virtualbox.org/

On the commandline at the root path of this repo, `vagrant up` should build a test deployment if the dependencies are met (internet access is required). The default build will use Centos since RHEL would require a license. You can use a free Red Hat Developers license if you prefer RHEL and don't have an active license. 