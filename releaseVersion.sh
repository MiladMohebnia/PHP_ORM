version=$1
jq ".version = \"${version}\"" composer.json > composer.json_temp
cat composer.json_temp > composer.json && rm composer.json_temp
git add .
git commit -am "tagging new version ${version}"
git tag "v${version}"
git push
git push --tags