# -*- mode: ruby -*-
# vi: set ft=ruby :
##
# find the puppeteer script
puppeteer = 'puppet/mr_rogers'
external_puppeteer = '../mr_rogers/' + puppeteer
require_relative puppeteer if File.exist?(puppeteer + '.rb')
require_relative external_puppeteer if !defined?(MrRogers) && File.exist?(external_puppeteer + '.rb')
raise 'Unable to build local development. Puppeteer unavailable.' if !defined?(MrRogers)

Vagrant.configure('2') do |v|
  # 1) load puppet/facts.yaml, 
  # 2) layer puppet/local-dev.facts.yaml on top if it exists, and 
  # 3) build
  MrRogers::box(v)
  ##
  # provisioners
  # you may optionally run additional provisioners before puppet, 
  # but usually that won't be needed
  # example : MrRogers::add_provisioner('')
  ##
  # setup the vm for puppet and puppet provision
  
  MrRogers::puppetize()
  ##
  # custom post puppetization provisioning can happen here
  # example : v.vm.provision "shell", inline: "echo Hello, World"
  # example : MrRogers::add_provisioner('')
  # example : MrRogers::add_provisioners(['',''])
  # optional (post puppetization) meta provisioners to manage apache, tone down selinux, etc.
  # example : MrRogers::add_helper('nano')
  # MrRogers::add_helpers(['nano','os','net'])
  # helper examples : 'scl+rh-php72' enable rh-php72 in scl
end