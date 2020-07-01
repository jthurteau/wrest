# -*- mode: ruby -*-
# vi: set ft=ruby :
##
# find the puppeteer script
puppeteer = 'puppet/mr_rogers'
external_puppeteer = '../mr_rogers/' + puppeteer
puppeteer_order = [puppeteer, external_puppeteer] # an external must be used for uninstall/update
puppeteer_order.each {|p| require_relative p if !defined?(MrRogers) && File.exist?("#{p}.rb")}
raise 'Unable to build local development. Puppeteer unavailable.' if !defined?(MrRogers)

Vagrant.configure('2') do |v|
  # defaults to building with puppet/facts.yaml + puppet/local-dev.facts.yaml
  MrRogers::box(v) #, {})
  ##
  # provisioners, optionally run additional provisioners before puppet
  # example : v.vm.provision "shell", inline: "echo Hello, World"
  # example : MrRogers::add_provisioner('')
  ##
  # setup the vm for puppet and puppet provision
  MrRogers::puppetize()
  ##
  # custom post puppetization provisioning can happen here
  # example : v.vm.provision "shell", inline: "echo Hello, World"
  # example : MrRogers::add_provisioner('name', hash)
  # optional (post puppetization) provisioners to manage apache, tone down selinux, etc.
  # MrRogers::add_helpers(['nano','os','net'])
  # helper examples : 'scl+rh-php72' enable rh-php72 in scl
end