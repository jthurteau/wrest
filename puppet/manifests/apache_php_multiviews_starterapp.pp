# --installed by module
# service { "httpd":
#   ensure  => "running",
#   require => Package["httpd"],
# }

# -- created by module
# group { 'apache':
#   ensure => 'present',
#   gid    => 1001,
# }

class { 'apache' : default_vhost => false }

class { 'apache::vhosts' :
  vhosts => {
    local_dev => {
      servername => 'default-local',
      serveradmin => 'root@localhost',
      docroot => "${doc_root}",
      port => '80',
      custom_fragment => '
        AddOutputFilter INCLUDES .php .html .shtml
        SetOutputFilter INCLUDES
        AddType application/x-httpd-php .php
        AddType application/json .json .php
        AddType application/xml .xml .php
      ',
      directories => [
        { 
          path => '/', 
          provider => 'location',
          options => [
            'Indexes',
            'FollowSymLinks',
            'MultiViews',
          ],
        }, { 
          path => "/${app}/vendor/saf", 
          provider => 'location',
          options => [
            'Indexes',
            'FollowSymLinks',
            'MultiViews',
          ],
        },
      ],
      aliases => [
        { 
          alias => "/${app}/vendor/saf",
          path => "${vagrant_root}/public",
        },
      ],
    },
  },
}
