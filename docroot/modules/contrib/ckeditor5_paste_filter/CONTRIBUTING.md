# Contribution guidelines

We welcome contributions of all types and sizes! This documentation may look a
bit scary but we don't expect a casual contributor to follow all of these
guidelines or tick all of these boxes. For example, maintainers and other
contributors will help write tests if a bugfix is missing test coverage, and
will rewrite commit messages to follow conventions.

This documentation serves the maintainers as well and outlines what is expected
of current and future maintainers.

See also [RELEASING.md](RELEASING.md).

## Coding standards

We follow the [Drupal coding standards](https://www.drupal.org/docs/develop/standards).

## Compiling JavaScript

From the project root:

```
yarn install # Only required the first time, or if dependencies change.
yarn build
```

There is also `yarn watch`, `yarn lint`, `yarn fix`.

## Test coverage

All bug fixes and new features must have test coverage, we have a suite of
Nightwatch tests that cover the functionality of the module.

Currently the tests are written and run against Nightwatch 2.x and Drupal 10.
We have plans to set up a GitLab CI job that will run tests against Drupal
9.5.x using Nightwatch 2.x.

### Running tests

Getting set up to run Nightwatch tests locally is covered in the [core tests
README] under the "Running Nightwatch tests" section.

The process for setting up to run tests will differ depending on your
development environment. Once you are set up, the following commands run from
inside the Drupal `core` folder will install the dependencies and run the full
test suite for this module.

```
yarn install
yarn test:nightwatch --tag ckeditor5_paste_filter
```

[core tests README]: https://git.drupalcode.org/project/drupal/-/blob/11.x/core/tests/README.md

## Committing

As much as possible, the maintainers aim to make focused, atomic commits that
can stand on their own and can fit into one of the types defined below.

Specifically, we follow the [conventional commits specification]. Our commit
messages are then used to generate release notes as well as determine the
semantic version of future releases. See [RELEASING.md](RELEASING.md) for how
this is used, and [package.json](package.json) for how this is set up.

[conventional commits specification]: https://www.conventionalcommits.org/en/v1.0.0/

### Commit types

- `chore`: Updates to internal tooling, release-related tasks, etc.
- `ci`: CI updates (GitLab CI or Drupal CI)
- `docs`: Documentation only changes
- `feat`: A new feature
- `fix`: A bug fix
- `perf`: A code change that improves performance
- `refactor`: A code change that neither fixes a bug nor adds a feature, and
  specifically does not change any functionality
- `style`: Changes that do not affect the meaning of the code (fixing code
  style lint errors, whitespace-only changes, formatting, etc)
- `test`: Testing updates

### Breaking changes

If a type is followed by `!`, that signifies a breaking change.

### Referencing Drupal.org issues

To reference a Drupal.org issue number, add it as a trailer to the git commit
message as shown in the example below. This will ensure that the commit on
GitLab links to the Drupal.org issue and vice versa. The issue will also be
linked in the changelog for the next release, assuming the commit is eligible
for changelog inclusion.

Currently, we use the following trailers to reference issues:

- `Fixes`: Fixes the bug described in the issue
- `Implements`: Implements the feature described in the issue
- `References`: References an issue

#### Example commit with trailer referencing Drupal.org issue

```
feat: turbo encabulator

Implements: #12345678
```

### Crediting contributors

When there is a single contributor to an issue, ensure that user is set as the
author of the git commit.

If there is more than one contributor to an issue, add them as `Co-authored-by`
trailers to the git commit message.

#### Example commit with Drupal.org issue reference and multiple contributor trailers

```
fix: repair broken marzlevane

Fixes: #87654321
Co-authored-by: Person <person@example.com>
Co-authored-by: Scott Zhu Reeves <8443-star-szr@users.noreply.drupalcode.org>
```
