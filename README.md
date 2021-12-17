#Install
```console
foo@bar:~$ composer global require infira/openapi-model-generator
foo@bar:~$ omg /path/to/your/config.yaml
```


#Config 
```yaml
destination: /path/to/create/modeles/into
spec: /swagger.yaml
mandatoryResponseProperties: true
rootNamespace: 'App\oam'
#pathNamesapceTemplate : '{tags[0]}/{method}/{path[1:*]}/{operationID}?{path[last]}'
# when object or property has nullable and default is not defined then handle default as null
nullableDefaultNull: true
```