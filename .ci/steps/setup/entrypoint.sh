#!/bin/bash

echo "Setting up the test environment..."

# Create a named volume
docker volume create my-named-volume

# Run the container with the volume
docker run --volume=my-named-volume:/drone/src test-vactory:latest

# Clean up
docker volume rm my-named-volume