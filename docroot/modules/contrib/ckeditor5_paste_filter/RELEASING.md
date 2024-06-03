# Releasing steps

1. Check CI status: https://www.drupal.org/pift-ci-job/2744796
2. Create the version bump commit and new version tag:

    a. For stable releases: `yarn bump` (semantic version will be determined
    based on commits)
    b. For unstable releases (alpha, beta, rc):
    `yarn bump-unstable [version-string]`, for example
    `yarn bump-unstable 2.0.0-rc1`
3. `git push` to push the version bump commit.
4. `git push --tags` to push the new tag.
5. `yarn changelog` to output changelog entries and a link to the diff for the
   whole release to stdout, these can be pasted into the release node for
   drupal.org in the next step.
6. Create release node: https://www.drupal.org/node/add/project-release/3349132
