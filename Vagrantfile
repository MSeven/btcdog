# -*- mode: ruby -*-
# vi: set ft=ruby :

# Vagrantfile API/syntax version. Don't touch unless you know what you're doing!
VAGRANTFILE_API_VERSION = "2"

$script = <<SCRIPT
    apt-get -y --no-install-recommends install php5-cli curl php5-curl git
    cd /vagrant
    curl -sSk https://getcomposer.org/installer | php
    php composer.phar install
SCRIPT

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|
    config.vm.box = "ubuntu/vivid64"
    config.vm.hostname = "btcdog-docker"

    if Vagrant.has_plugin?("vagrant-cachier")
        config.cache.scope = :box
    end

     config.vm.provision "shell", inline: $script
end
