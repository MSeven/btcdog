# -*- mode: ruby -*-
# vi: set ft=ruby :

# Vagrantfile API/syntax version. Don't touch unless you know what you're doing!
VAGRANTFILE_API_VERSION = "2"

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|
    config.vm.box = "ubuntu/trusty64"
    config.vm.hostname = "btcdog-docker"

    if Vagrant.has_plugin?("vagrant-cachier")
        config.cache.scope = :box
    end
    config.vm.provision "shell",
        inline: "apt-get update; apt-get -y upgrade"
    config.vm.provision "docker" do |d|
        d.build_image "/vagrant/"
    end
end
