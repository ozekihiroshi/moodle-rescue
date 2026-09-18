## 復習：境界と分岐の順序を確かめる

次のコードを`score`が79、80、90のときに追ってください。90のとき、`REVIEW`も表示されるでしょうか。

```python
if score >= 90:
    print("ON TRACK")
elif score >= 80:
    print("REVIEW")
else:
    print("SUPPORT")
```

> [!ANSWER]
> 79では`SUPPORT`、80では`REVIEW`、90では`ON TRACK`だけです。一連の`if / elif / else`は、最初に真となる分岐だけを実行します。

Labでは先に`score = 79`などを置いてから実行します。条件を入れ替えた場合の違いも予想できるか確かめてください。
