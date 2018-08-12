# fxBot
Work in progress
TODO

## You will need oanda account and api token in order to use this app (practice account recommended)

### How to start dev environment
- install [virtualbox](https://www.virtualbox.org/)
- clone repo
```
git clone https://github.com/lukasztecza/fxBot.git
```

- go to repo and run vagrant
```
cd ./fxBot
vagrant up
```

- generate password hash that you can use for parameters.json creation
```
php ./tools/passwordHash.php
```

- generate parameters.json
```
php tools/createParameters.php
```

- sample settings for parameters.json
```
environment: dev
databaseEngine: mysql
databaseHost: localhost
databasePort: 3306
databaseName: fx_bot
databaseUser: user
databasePassword: pass
inMemoryUsername: user
inMemoryPasswordHash: $2y$12$gNxhxS/wtPahKqiaueQQXuX14jK.F6dco01MBBFcFcekkh1rjMct2
oandaApiUri: https://api-fxpractice.oanda.com
oandaApiKey: some_oanda_api_key
oandaAccount: some_oanda_account
forexFactoryUri: https://www.forexfactory.com
selectedStrategy: FxBot\\Model\\Strategy\\RigidDeviationStrategy
instrument: EUR_USD
homeCurrency: CAD
singleTransactionRisk: 0.01
rigidStopLoss: 0.0025
takeProfitMultiplier: 3
lossLockerFactor: 2
longFastAverage: 30
longSlowAverage: 60
signalFastAverage: 10
signalSlowAverage: 20
bankFactor: 1
inflationFactor: 1
tradeFactor: 1
companiesFactor: 1
salesFactor: 1
unemploymentFactor: 1
bankRelativeFactor: 1
extremumRange: 10
followTrend: 1
lastPricesPeriod: P30D
lastIndicatorsPeriod: P90D
```

- navigate to `localhost:8080` in order to see if the project works

- go to the machine and run commands to populate
```
vagrant ssh
cd /app
php scripts/command.php populatePricesCommand
php scripts/command.php populateIndicatorsCommand
```

- fetching services grab only 2 weeks of data during one run so it will require much time to gather reasonable amount of data

- you may find it usefull to use cronjob for that
```
vagrant ssh
crontab -e
```

- add these lines
```
*/1 * * * * php /app/scripts/command.php populatePricesCommand
*/1 * * * * php /app/scripts/command.php populateIndicatorsCommand
```

- check if a cronjob runs
```
sudo tail /var/log/syslog
```

- leave it for as long as you need

- when you fetched enough data for your dev comment out cronjobs
```
#*/1 * * * * php /app/scripts/command.php populatePricesCommand
#*/1 * * * * php /app/scripts/command.php populateIndicatorsCommand
```

### Usage (assuming you are in machine in /app directory)
- run simulation with configuration in `src/Model/Command/SimulationCommand.php`
```
php scripts/command simulationCommand
```
- you should be able to see simulation output
- simulation results are stored in db `fx_bot`.`simulation` table

- run learning with configuration in `src/Model/Service/LearningService.php`
```
php scripts/command learningCommand
```
- you should be able to see learning output

- by default simulations and learnings use RigidRandomStrategy which uses rand() to decide if it should sell or buy
- you change configuration of SimulationCommand or LearningService and choose/create other strategy from `src/Model/Strategy` directory
- you can play around with different parameters in these classes and maybe you will be able to find what works
- so far I could not find anything that works well on live account event though some tests were quite promissing :/

- have fun and good luck

### How to test
- bootstrap codeception
```
php vendor/codeception/codeception/codecept bootstrap
```

- run tests
```
php vendor/codeception/codeception/codecept run
```

### Working on front-end
- inside vagrant machine you can webpack watch your assets
```
vagrant ssh
cd /app
npm run watch
```

- when you finished clean dev builds created by watch and build final assets
```
npm run build
```
