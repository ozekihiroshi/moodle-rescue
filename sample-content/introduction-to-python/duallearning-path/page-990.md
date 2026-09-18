## The supported Python environment

Moodle contains explanations, learning checks, and assignments; Python Lab executes the code. Opening “Python Lab 00” from Moodle leads to `00_start_here.ipynb` in the learner workspace. Even on a shared computer, the file is stored in the server-side workspace opened through the browser, so learners do not install Python separately on each PC.

Python can be run in several ways. A Console accepts one instruction at a time. A `.py` script normally runs a saved sequence from top to bottom. A Notebook keeps explanation, code, and visible results together in cells. Because that record supports both practice and review, the Notebook is the course standard.

| Method                     | Best suited to                                       | Course use                                           |
|----------------------------|------------------------------------------------------|------------------------------------------------------|
| Console (interactive mode) | Trying a short expression or instruction immediately | Small experiments when useful                        |
| `.py` script               | Running a saved sequence of instructions             | Understand common Python execution                   |
| Notebook                   | Keeping explanation, code, and results together      | **Standard for examples, practice, and submissions** |

### Run the first cell

A Notebook has Markdown cells for prose and Code cells for Python. Select a Code cell and press <span class="kbd">Shift</span>+<span class="kbd">Enter</span>; Python runs that cell and returns the result below it. The following code displays text and then a calculated value.

```
print("Python Lab is ready")
print(3 + 4)
```

The quoted part on the first line is text to display. On the second line, `3 + 4` is calculated before it is displayed. Do not try to memorise the syntax yet. Change one value, rerun the cell, and observe what changes.

### Running state and the saved Notebook

When a Code cell runs, the Python kernel keeps values in memory. The number beside a cell records actual execution order, not its position on the page. Running a later cell first can therefore create a state that another reader cannot reproduce from top to bottom.

If the visible results stop matching the explanation, restart the kernel and run every cell from the top. This checks that the Notebook is reproducible from a clean state. Saving with <span class="kbd">Ctrl</span>+<span class="kbd">S</span> preserves code, prose, and visible results in the Notebook file. Temporary kernel state and the saved file are not the same thing.

### An error is evidence about the next step

An unclosed quote prevents Python from reading an instruction and produces `SyntaxError`. Valid syntax that uses an unknown name fails during execution with `NameError`. In both cases, the final traceback line gives an exception name and a concise message. Read that line first, inspect the named cell, change one thing, and run it again.

A program can also run and return the wrong answer. If a task says “three days, four sessions per day”, `3 + 4` runs and returns 7, but it does not represent the task. Successful execution and a correct result are different, which is why you predict before running and explain after checking.

### Complete Python Lab 00

1.  Open `00_start_here.ipynb` and run its Code cells from the top.
2.  Change one value, rerun it, and explain the changed result in a Markdown cell.
3.  Observe the prepared syntax and runtime errors and read the exception name on the final line.
4.  Restart the kernel and run all cells from the top.
5.  Save, close, and reopen the Notebook to confirm that your change remains.

Once this works, the next chapter can focus on Python rather than setup. If the workspace will not open or the server does not respond, do not change the managed environment. Report the Moodle activity name and the final visible error line to the teacher or administrator.

**Estimated study time:** 45–60 minutes
