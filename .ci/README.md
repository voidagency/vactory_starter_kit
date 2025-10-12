# Vactory Test CI

## Prerequisites

The following environment variables are required to build and run the test container:

- `DRONE_SOURCE_BRANCH` - The source branch name (e.g., `3.x`, `main`)
- `DRONE_COMMIT` - The commit hash to test
- `BITBUCKET_AUTHTOKEN` - Bitbucket authentication token for accessing private repositories

## Usage

### Set Environment Variables

```bash
export DRONE_SOURCE_BRANCH=3.x
export DRONE_COMMIT=da649ed176baf268cf17b3aaff5bee273c27ec30
export BITBUCKET_AUTHTOKEN=your_token_here
```

### Commands

**Build Docker image:**
```bash
make build-tests
```

**Run tests (exits after completion):**
```bash
make run-tests
```

**Run tests in development mode (keeps container running):**
```bash
make run-tests-dev
```

**Access running container (when using dev mode):**
```bash
docker exec -it <container_id> bash
```

## Notes

- The build process will fail if any of the required environment variables are not set
- Development mode keeps the container running after tests complete, allowing you to debug or inspect the environment
- All MySQL credentials and cache keys are dynamically generated for security
