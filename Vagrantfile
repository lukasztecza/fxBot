# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure("2") do |config|
  config.vm.box = "ubuntu/trusty64"
  config.vm.network "forwarded_port", guest: 8080, host: 8080
  config.vm.synced_folder ".", "/app", :owner=> 'www-data', :group=>'vagrant', :mount_options => ["dmode=775","fmode=664"]
  config.vm.provider :virtualbox do |vb|
    vb.customize ["modifyvm", :id, "--memory", "1024"]
  end
  config.vm.provision :shell, path: "./tools/bootstrap.sh"
end
