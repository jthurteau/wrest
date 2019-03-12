# vcsrepo { "${app_path}/${app}" :
#   ensure   => present,
#   force => true,
#   provider => git,
#   source   => 'git://github.com/jthurteau/saf-shell.git',
#   require => File["${app_path}"],
# }

file { "${app_path}/${app}" :
  ensure  => link,
  force   => true,
  target  => "/app",
  require => File["${app_path}"],
}

file { "${app_path}/${app}/public/" :
  ensure  => directory,
  require => File["${app_path}/${app}"],
  # require => VCsrepo["${app_path}/${app}"],
}

file { "${doc_root}/${app}" :
  ensure  => link,
  force   => true,
  target  => "${app_path}/${app}/public",
  require => [
    File["${app_path}/${app}/public/"],
    File["${doc_root}"]
    ],
}

file { "${app_path}/${app}/localize.php" :
  ensure => file,
  mode  => '0755',
  source => "${puppet_files}/localize.php",
  require => File["${app_path}/${app}/public/"],
}


# file { "${app_path}/${app}/public/vendor" :
#   ensure  => directory,
#   require => File["${app_path}/${app}/public"],
# }

# file { "${app_path}/${app}/public/vendor/saf" :
#   ensure  => link,
#   force   => true,
#   target  => "${vagrant_root}/public",
#   require => [
#     File["${app_path}/library/Saf"],
#     File["${app_path}/${app}/public/vendor"]
#   ],
# }

#TODO copy saf/public to  app public/vendor/saf
