'use strict';

var AWS = require('aws-sdk');
var ecs = new AWS.ECS();

module.exports.ecsRunTask = (events, context) => {
	var params = {
		taskDefinition: 'phpstan-playground-cli',
		cluster: 'phpstan',
		count: 1,
		overrides: {
			containerOverrides: [
				{
					'name': 'cli',
					'command': ['versions:refresh']
				}
			]
		}
	};
	ecs.runTask(params, function(err, data) {
		if (err) console.log(err, err.stack); // an error occurred
		else     console.log(data);           // successful response
		context.done(err, data)
	})

};
