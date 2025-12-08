#!/usr/bin/env bash

set -e

CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)

function remote()
{
    git remote add "$1" "$2" 2>/dev/null || true
}

function split()
{
        LOCAL_SHA=$(git subtree split --prefix="$1")
        REMOTE_SHA=$(git ls-remote "$2" "refs/heads/$CURRENT_BRANCH" | awk '{print $1}')
        if [ "$LOCAL_SHA" = "$REMOTE_SHA" ]; then
            echo ">>> No changes for $1 — skipping."
            return
        fi
        echo ">>> Changes detected for $1 — pushing..."
        git push --no-verify "$2" "$LOCAL_SHA:refs/heads/$CURRENT_BRANCH" -f
}


git pull origin "$CURRENT_BRANCH"

remote module-accounts-manager git@github.com:epsicube/module-accounts-manager.git
remote module-administration git@github.com:epsicube/module-administration.git
remote module-execution-platform git@github.com:epsicube/module-execution-platform.git
remote module-hypercore git@github.com:epsicube/module-hypercore.git
remote module-mailing-system git@github.com:epsicube/module-mailing-system.git
remote foundation git@github.com:epsicube/foundation.git
remote schemas git@github.com:epsicube/schemas.git
remote support git@github.com:epsicube/support.git
remote docs git@github.com:epsicube/docs.git

split 'modules/AccountsManager' module-accounts-manager &
split 'modules/Administration' module-administration &
split 'modules/ExecutionPlatform' module-execution-platform &
split 'modules/Hypercore' module-hypercore &
split 'modules/MailingSystem' module-mailing-system &
split 'packages/Foundation' foundation &
split 'packages/Schemas' schemas &
split 'packages/Support' support &
split 'docs' docs &

wait
