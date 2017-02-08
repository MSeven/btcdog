# -*- mode: ruby -*-
# vi: set ft=ruby :

# Vagrantfile API/syntax version. Don't touch unless you know what you're doing!
VAGRANTFILE_API_VERSION = "2"

$script = <<SCRIPT
    apt-get update
    apt-get -y --no-install-recommends install php5-cli curl php5-curl git
    cd /vagrant
    curl -sSk https://getcomposer.org/installer | php
    php composer.phar install
SCRIPT

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|
    config.vm.box = "bento/debian-8.6"
    config.vm.hostname = "btcdog-docker"

    config.vm.provider :virtualbox do |v|
        v.customize ["modifyvm", :id, "--natdnshostresolver1", "on"]
        v.customize ["modifyvm", :id, "--memory", 2048]
    end

    if Vagrant.has_plugin?("vagrant-cachier")
        config.cache.scope = :box
        config.cache.enable :apt
        config.cache.enable :composer
    end

     config.vm.provision "shell", inline: $script
end
