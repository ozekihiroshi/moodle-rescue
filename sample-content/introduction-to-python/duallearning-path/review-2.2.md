## Review: Distinguish return values from exceptions

Should a search that returns `None` for a missing record be handled like an update that raises `KeyError`? Revisit the function contracts and tests in 2.2.

> [!ANSWER]
> Their contracts differ. After searching, check for `None` and choose what to do next. An update requires an existing target and raises the specified exception if none exists. Test missing targets and state changes as well as successful return values.

Compare the expected behaviour with the actual function contract before changing your code.
