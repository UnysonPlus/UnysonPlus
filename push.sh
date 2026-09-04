#!/bin/bash

# Get current folder name
FOLDER_NAME=$(basename "$PWD")

# Build GitHub URL automatically
REPO_URL="https://github.com/UnysonPlus/$FOLDER_NAME.git"

# Detect current branch
BRANCH=$(git rev-parse --abbrev-ref HEAD)

# Check for changes, excluding this script
if [ -n "$(git status --porcelain | grep -v "push.sh")" ]; then
    # Stage all changes except push.sh
    git add . ':!push.sh'

    # Commit changes
    #git commit -m "Updated Manifest"
    git commit -m "Added Number Option Type"

    # Push to GitHub
    git push $REPO_URL $BRANCH

    echo "Changes committed and pushed successfully."
else
    echo "No changes to commit."
fi
