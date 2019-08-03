case $COMP_CWORD in
    1)
        # do nothing
    ;;
    *)
        case ${first} in
            feature | hotfix)
                words+=" --from-this --disable-db --reload-db"
            ;;
        esac
    ;;
esac
