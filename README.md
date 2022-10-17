# QuoteBy

A xenforo plugin that places a blockmessage on posts which have been quoted in the thread.

Open source for all with license.

## Installation

Download from release page the latest version and install addon as usual.

On install, it will scan all posts that contains a quote tag to populate the database. This can take some time to do.

## Todo

### Rebuild cache with diff

Currently the rebuild deletes everything in the table and repopulates it. We should improve this method by doing a diff between existing QuoteBy, adding and removing when necessary.