vcsrepo { "${app_vendor_path}/header" :
  ensure   => latest,
  provider => git,
  source   => "https://${developer}:${ghe_pat}@github.ncsu.edu/ncsu-libraries/header.git",
  require => File["${app_vendor_path}"],
  revision => "v${lib_header_version}",
}

vcsrepo { "${app_vendor_path}/footer" :
  ensure   => latest,
  provider => git,
  source   => "https://${developer}:${ghe_pat}@github.ncsu.edu/ncsu-libraries/footer.git",
  require => File["${app_vendor_path}"],
  revision => "v${lib_footer_version}",
}
