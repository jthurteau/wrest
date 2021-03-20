# -*- mode: ruby -*-
# vi: set ft=ruby :
##
# find the vuppeteer script
vuppeteer = 'vuppet/mr' # default path
vuppeteer_order = [vuppeteer, '../mr/' + vuppeteer] # where to look, i.e. internal then external
vuppeteer_order.each {|v| require_relative v if !defined?(Mr) && File.exist?("#{v}.rb")}
raise 'Unable to build Local Development Environment. Vuppeteer unavailable.' if !defined?(Mr)

options = {

}

Vagrant.configure('2') do |v|
  # defaults to building with options + vuppet/vuppeteer.yaml + vuppet/local-dev.vuppeteer.yaml
  Mr::vagrant(v, options)
  ## provisioners, optionally run additional provisioners before puppet
  # example : v.vm.provision "shell", inline: "echo Hello, World"
  # example : Mr::add_provisioner('name'[, hash, when])
  Mr::puppet_apply()
  ## custom post puppetization provisioning can happen here
  # example : v.vm.provision "shell", inline: "echo Goodbye, World"
  # example : Mr::add_provisioner('name'[, hash, when])
  Mr::helpers()
end