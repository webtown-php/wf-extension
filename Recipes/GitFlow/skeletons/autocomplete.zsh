case $state in
    parameters)
        case $words[2] in
            feature | hotfix)
                _arguments '*: :(--from-this --disable-db --reload-db)'
            ;;
        esac
    ;;
esac
