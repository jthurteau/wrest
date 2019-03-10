# -*- mode: ruby -*-
# vi: set ft=ruby :
##
# project settings
app = 'saf'
org_domain = 'lib.ncsu.edu'
developer = 'jthurtea'
realm_name = 'ncsu-delta'
app_stack = 'apache_php_multiviews-rh_php72-sample_docroot'
##
# vagrant/puppet specific preferences
vagrant_guest_path = '/vagrant'
org_stack = org_domain.split('.').reverse.join('-')
manifest_stack = "#{org_stack}-#{app_stack}-developer:#{developer}-app:#{app}"
# NOTE -- manifest_stack merge from external is not available yet
# NOTE -- files in the external puppet path are NOT available to Puppet 
#         (e.g. managed files like info.php, localize.php, php.conf) are
#         only available from the local puppet folder
puppet_modules = [
  # 'puppetlabs-postgresql', 
  'puppetlabs-apache', 
  'puppetlabs-mysql',
  'puppetlabs-vcsrepo', 
  # 'puppet-python',
]
puppet_verbose = true
puppet_debug = true
log_to= ['console', 'file'][0]
##
# find the puppeteer script and
puppeteer = 'puppet/mr_rogers'
external_puppeteer = '../mr_rogers/' + puppeteer
require_relative puppeteer if File.exist?(puppeteer + '.rb')
require_relative external_puppeteer if !defined?(MrRogers) && File.exist?(external_puppeteer + '.rb')
raise 'Unable to build local development. Puppeteer unavailable.' if !defined?(MrRogers)

MrRogers::configure('5', puppet_modules, 'puppet')
MrRogers::init('puppet/.facts', manifest_stack, 'local-dev.pp')
MrRogers::realm(realm_name) if realm_name 

Vagrant.configure('2') do |config|
  MrRogers::box("#{developer}-#{app}", config)

  # config.vm.box_check_update = false
  ##
  # forwarded ports
  config.vm.network 'forwarded_port', guest: 80, host: 8080, host_ip: '127.0.0.1'
  config.vm.network 'forwarded_port', guest: 8001, host: 8081, host_ip: '127.0.0.1'
  ##
  # shared folders
  config.vm.synced_folder '.', vagrant_guest_path, owner: 'vagrant', group: 'vagrant', type: 'virtualbox'
  ##
  # providers
  config.vm.provider 'virtualbox' do |vb|
    vb.gui = false
    vb.memory = "1024"
  end
  ##
  # provisioners
  MrRogers::register(org_domain)
  MrRogers::puppetize("--verbose --debug", log_to, vagrant_guest_path)
  
  # this is where custom provisioning typically happens

  MrRogers::add_helpers(['nano','os']) #optional (provisioners to manage apache, tone down selinux, etc.)
end