import {AWSError, ECS} from 'aws-sdk';
import {PromiseResult} from "aws-sdk/lib/request";

const ecs = new ECS();

export async function ecsRunTask(): Promise<PromiseResult<ECS.Types.RunTaskResponse, AWSError>> {
	const params = {
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

	return await ecs.runTask(params).promise();
}
